<?HH
/**
 * Hack Fast Algos
 *
 * Stripped down implementation of Strassen's Matrix Multiplication
 * Learn more @link https://en.wikipedia.org/wiki/Strassen_algorithm
 */

namespace HackFastAlgos;

class MatrixMultiplyNotTwoByTwoException extends \Exception{}

newtype Matrix = Vector<Vector<int>>;

class MatrixMultiply
{
	public function __construct(protected Matrix $matrix1, protected Matrix $matrix2) {}

	/**
	 * Operates in Theta(1) time.
	 */
	public function multiply() : Matrix
	{
		$m1 = $this->matrix1;
		$m2 = $this->matrix2;

		$result = Vector{Vector{0,0}, Vector{0,0}};

		$this->throwIfNotBothTwoByTwo();

  		$step1 = ($m1[0][0] +  $m1[1][1]) * ($m2[0][0] + $m2[1][1]);
		$step2 = ($m1[1][0] +  $m1[1][1]) *  $m2[0][0];
		$step3 =  $m1[0][0] * ($m2[0][1]  -  $m2[1][1]);
		$step4 =  $m1[1][1] * ($m2[1][0]  -  $m2[0][0]);
		$step5 = ($m1[0][0] +  $m1[0][1]) *  $m2[1][1];
		$step6 = ($m1[1][0] -  $m1[0][0]) * ($m2[0][0] + $m2[0][1]);
		$step7 = ($m1[0][1] -  $m1[1][1]) * ($m2[1][0] + $m2[1][1]);

		$result[0][0] = (int) ($step1 + $step4 - $step5 + $step7);
		$result[0][1] = (int) ($step3 + $step5);
		$result[1][0] = (int) ($step2 + $step4);
		$result[1][1] = (int) ($step1 - $step2 + $step3 + $step6);

		return $result;
	}

	private function throwIfNotBothTwoByTwo()
	{
		if (
			$this->matrix1->count() !== 2 ||
			$this->matrix2->count() !== 2 ||
			$this->matrix1[0]->count() !== 2 ||
			$this->matrix2[0]->count() !== 2
		) {
			throw new MatrixMultiplyNotTwoByTwoException();
		}
	}
}
